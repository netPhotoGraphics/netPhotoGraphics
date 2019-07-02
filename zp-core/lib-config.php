<?php

/**
 * configuration handler functions
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
class configFile {

	/**
	 * Updates an item in the configuration file
	 * @param unknown_type $item
	 * @param unknown_type $value
	 * @param unknown_type $quote
	 */
	static function update($item, $value, $_config_contents, $quote = true) {
		if ($quote) {
			$value = "'" . addslashes($value) . "'";
		}
		$i = strpos($_config_contents, $item);
		if ($i === false) {
			$parts = preg_split('~\/\*.*Do not edit below this line.*\*\/~', $_config_contents);
			if (isset($parts[1])) {
				$_config_contents = $parts[0] . "\$conf['" . $item . "'] = " . $value . ";\n/** Do not edit below this line. **/" . $parts[1];
			} else {
				trigger_error(gettext('The configuration file is corrupt. You will need to restore it from a backup.'), E_USER_ERROR);
			}
		} else {
			$i = strpos($_config_contents, '=', $i);
			$j = strpos($_config_contents, "\n", $i);
			$_config_contents = substr($_config_contents, 0, $i) . '= ' . $value . ';' . substr($_config_contents, $j);
		}
		return $_config_contents;
	}

	/**
	 * backs-up and updates the configuration file
	 *
	 * @param string $_config_contents
	 */
	static function store($_config_contents, $folder = NULL) {
		if (is_null($folder)) {
			$folder = SERVERPATH . '/';
		}
		$mod = fileperms($folder . DATA_FOLDER . '/' . CONFIGFILE) & 0777;

		@rename($folder . DATA_FOLDER . '/' . CONFIGFILE, $backkup = $folder . DATA_FOLDER . '/' . stripSuffix(CONFIGFILE) . '.bak.php');
		@chmod($backup, $mod);
		file_put_contents($folder . DATA_FOLDER . '/' . CONFIGFILE, $_config_contents);
		clearstatcache();
		@chmod($folder . DATA_FOLDER . '/' . CONFIGFILE, $mod);
	}

}
