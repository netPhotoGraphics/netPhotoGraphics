<?php

/*
 * The deprecated_functions class
 *
 * @package plugins/deprecated-functions
 */

define('DEPRECATED_LOG', SERVERPATH . '/' . DATA_FOLDER . '/deprecated.log');

class deprecated_functions {

	var $listed_functions = array();
	var $unique_functions = array();

	function __construct() {

		if (OFFSET_PATH == 2) {
			//clean up the mess from previous implementation
			$sql = 'SELECT * FROM ' . prefix('options') . ' WHERE `name` LIKE "deprecated_%"';
			$result = query_full_array($sql);
			foreach ($result as $option) {
				if ($option['name'] != 'deprecated_functions_signature') {
					purgeOption($option['name']);
				}
			}
		}
		foreach (getPluginFiles('*.php') as $extension => $plugin) {
			$deprecated = stripSuffix($plugin) . '/deprecated-functions.php';
			if (file_exists($deprecated)) {
				$plugin = basename(dirname($deprecated));
				$content = file_get_contents($deprecated);
				$content = preg_replace('~#(.*)\n~', '', $content);
				preg_match_all('~@deprecated\s+.*since\s+.*(\d+\.\d+\.\d+)~', $content, $versions);
				preg_match_all('/([public static|static]*)\s*function\s+(.*)\s?\(.*\)\s?\{/', $content, $functions);
				if ($plugin == 'deprecated-functions') {
					$plugin = 'core';
					$suffix = '';
				} else {
					$suffix = ' (' . $plugin . ')';
				}
				foreach ($functions[2] as $key => $function) {
					if ($functions[1][$key]) {
						$flag = '_method';
						$star = '*';
					} else {
						$star = $flag = '';
					}
					$name = $function . $star . $suffix;
					$option = 'deprecated_' . $plugin . '_' . $function . $flag;

					$this->unique_functions[strtolower($function)] = $this->listed_functions[$name] = array(
							'plugin' => $plugin,
							'function' => $function,
							'class' => trim($functions[1][$key]),
							'since' => @$versions[1][$key],
							'option' => $option,
							'multiple' => array_key_exists($function, $this->unique_functions));
				}
			}
		}
	}

	static function tabs($tabs) {
		if (npg_loggedin(ADMIN_RIGHTS)) {
			if (!isset($tabs['development'])) {
				$tabs['development'] = array('text' => gettext("development"),
						'link' => getAdminLink(PLUGIN_FOLDER . '/deprecated-functions/admin_tab.php') . '?page=development&tab=deprecated',
						'subtabs' => NULL);
			}
			$tabs['development']['subtabs'][gettext("deprecated")] = PLUGIN_FOLDER . '/deprecated-functions/admin_tab.php?page=development&tab=deprecated';
			$tabs['development']['subtabs'][gettext('Check deprecated')] = PLUGIN_FOLDER . '/deprecated-functions/check_for_deprecated.php?tab=checkdeprecated';
		}
		return $tabs;
	}

	/**
	 * log writer
	 * @param type $msg
	 */
	static function log($msg) {
		global $_mutex;
		if (is_object($_mutex))
			$_mutex->lock();
		$f = fopen(DEPRECATED_LOG, 'a');
		if ($f) {
			fwrite($f, $msg . "\n");
			fclose($f);
			clearstatcache();
			chmod(DEPRECATED_LOG, LOG_MOD);
		}
		if (is_object($_mutex))
			$_mutex->unlock();
	}

	/*
	 * used to provided deprecated function notification.
	 *
	 * @param $message the instructions for mitigation
	 * @param $fcn used for handling "magic call" class methods
	 */

	static function notify($message, $fcn = NULL) {
		$traces = debug_backtrace();
		foreach ($traces as $key => $trace) {
			if (!array_key_exists('file', $trace) || basename($trace['file']) != 'deprecated-functions.php') {
				break;
			}
			unset($traces[$key]);
		}

		if ($fcn) { //	static call
			array_shift($traces); //	remove the _CALL container
			$traces = array_values($traces);
		} else {
			$traces = array_values($traces);
			$fcn = $traces[0]['function'];

			if (empty($fcn)) {
				$fcn = gettext('function');
			}
		}

		if (!empty($message)) {
			$message = ' ' . $message;
		}
		//get the container folder
		if (isset($traces[0]['file']) && isset($traces[0]['line'])) {
			$path = explode('/', replaceScriptPath($traces[0]['file'])); //	NB: this fails if symlinking is involved
			switch (array_shift($path)) {
				case THEMEFOLDER:
					$script = sprintf(gettext('theme %1$s:%2$s'), array_shift($path), array_pop($path));
					break;
				case USER_PLUGIN_FOLDER:
					$script = sprintf(gettext('user plugin %1$s:%2$s'), array_shift($path), array_pop($path));
					break;
				case PLUGIN_FOLDER:
					$script = sprintf(gettext('standard plugin %1$s:%2$s'), array_shift($path), array_pop($path));
					break;
				case CORE_FOLDER:
					$script = sprintf(gettext('core:%s'), array_pop($path));
					break;

				default:
					$script = array_pop($path);
					break;
			}
			$line = $traces[0]['line'];
		} else {
			$script = $line = gettext('unknown');
		}
		$output = sprintf(gettext('<code>%1$s</code> (called from %2$s line %3$s) is deprecated.'), $fcn, $script, $line) . "\n&nbsp;&nbsp;" . $message . "\n";

		if (file_exists(DEPRECATED_LOG)) {
			$content = file_get_contents(DEPRECATED_LOG);
			$log = !preg_match('~' . preg_quote($output) . '~', $content);
		} else {
			$log = true;
		}
		if ($log) {
			if (@$traces[1]['class']) {
				$flag = '_method';
			} else {
				$flag = '';
			}

			$prefix = "&nbsp;&nbsp;";
			$line = '';
			$caller = '';

			array_shift($traces);
			foreach ($traces as $b) {
				$caller = (isset($b['class']) ? $b['class'] : '') . (isset($b['type']) ? $b['type'] : '') . $b['function'];
				if (!empty($line)) { // skip first output to match up functions with line where they are used.
					$prefix .= "&nbsp;&nbsp;";
					$output .= 'from ' . $caller . ' (' . $line . ")\n" . $prefix;
				} else {
					$output .= '&nbsp;&nbsp;' . $caller . " called ";
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
			self::log($output);
		}
	}

	static function notify_call($method, $message) {
		self::notify($message, $method);
	}

	static function logZPCore($uri) {
		$parts = parse_url($uri);
		$use = ltrim(str_replace(WEBPATH, '', $parts['path']), '/');
		$use = strtr($use, array('zp-core/zp-extensions' => 'zp-extensions', 'zp-core/' => ''));
		$use = getAdminLink($use, '');
		if (isset($_SERVER['HTTP_REFERER'])) {
			$refs = parse_url($_SERVER['HTTP_REFERER']);
			if (basename(dirname($parts['path'])) == 'setup') {
				//	don't log it if setup did it.
				return;
			}
			$output = sprintf(gettext('The use of <code>zp-core</code> in the URL <code>%1$s</code> referred from <code>%2$s</code> is deprecated.'), $uri, $_SERVER['HTTP_REFERER']);
		} else {
			$output = sprintf(gettext('The use of <code>zp-core</code> in the URL <code>%1$s</code> is deprecated.'), $uri);
		}
		$output .= "\n&nbsp;&nbsp;" . sprintf(gettext('Use <code>%1$s</code> instead.'), $use);

		if (file_exists(DEPRECATED_LOG)) {
			$content = file_get_contents(DEPRECATED_LOG);
			$log = !preg_match('~' . preg_quote($parts['path']) . '~', $content);
		} else {
			$log = true;
		}
		if ($log) {
			self::log($output);
		}
	}

}
