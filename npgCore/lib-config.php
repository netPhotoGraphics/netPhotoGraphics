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
			$value = "'" . addslashes(strval($value)) . "'";
		}
		if (preg_match('~\$conf\[[\'"]' . $item . '[\'"]\]~', $_config_contents, $matches, PREG_OFFSET_CAPTURE)) {
			$i = $matches[0][1];
			$j = strpos($_config_contents, "\n", $i);
			$_config_contents = substr($_config_contents, 0, $i) . '$conf[\'' . $item . '\'] = ' . $value . ';' . substr($_config_contents, $j);
		} else {
			$parts = preg_split('~\/\*.*Do not edit below this line.*\*\/~', $_config_contents);
			if (isset($parts[1])) {
				$_config_contents = $parts[0] . "\$conf['" . $item . "'] = " . $value . ";\n/** Do not edit below this line. **/" . $parts[1];
			} else {
				trigger_error(gettext('The configuration file is corrupt. You will need to restore it from a backup.'), E_USER_ERROR);
			}
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
		$backup = $folder . DATA_FOLDER . '/' . stripSuffix(CONFIGFILE) . '.bak.php';
		if (file_exists($backup)) {
			chmod($backup, 0777);
			unlink($backup);
		}
		rename($folder . DATA_FOLDER . '/' . CONFIGFILE, $backup);
		chmod($backup, $mod);
		file_put_contents($folder . DATA_FOLDER . '/' . CONFIGFILE, $_config_contents);
		chmod($folder . DATA_FOLDER . '/' . CONFIGFILE, $mod);
	}

}
