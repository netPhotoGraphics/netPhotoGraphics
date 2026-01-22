<?php
/**
 * The plugin provides IP address filtering:
 *
 * Allows/Denies access to the gallery to specified IP address ranges.
 * Detects repeated failed login attempts and blocks access to the IP address used
 * in these attempts.
 *
 * IP addresses may be supplied in a text file, one IP range per line. Upload the text file to the <i>%USER_PLUGIN_FOLDER%/ipBlocker/</i> folder. An IP range is a starting IP and ending IP separated by a hyphen. E.g. <var>192.168.0.0-192.168.1.255</var>.
 *
 * If you accidentally block your own IP address you will have to remove the <i>%DATA_FOLDER%/ipBlockerLists</i> file
 * to clear out any block ranges.
 *
 * @author Stephen Billard (sbillard)
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @deprecated since 2.00.18 no longer supported, use .htaccess Deny from instead
 *
 * @package plugins/ipBlocker
 * @pluginCategory security
 */
$plugin_is_filter = 990 | FEATURE_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Tool to block hacker access to your site.");
}

$option_interface = 'ipBlocker';

/**
 * Option handler class
 *
 */
class ipBlocker {

	/**
	 * class instantiation function
	 *
	 * @return security_logger
	 */
	function __construct() {

		if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists')) {
			if (OFFSET_PATH == 2) {
				$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="ipBlocker"';
				query($sql);

				purgeOption('ipBlocker_type');
				purgeOption('ipBlocker_threshold');
				purgeOption('ipBlocker_404_threshold');
				purgeOption('ipBlocker_timeout');

				if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists')) {
					$raw = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists');
					$_ipBlocker_list = getSerializedArray($raw);
					if (isset($_ipBlocker_list['Suspend']) || isset($_ipBlocker_list['Options']) || isset($_ipBlocker_list['Block'])) {
						if (isset($_ipBlocker_list['Block'])) {
							self::setList($_ipBlocker_list['Block']);
						} else {
							self::setList(array());
						}
					}
				}
			}
		}
	}

	static function delete() {
		if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists')) {
			unlink(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists');
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$buttons = array(gettext('Allow') => 1, gettext('Block') => 0);
		$text = array_flip($buttons);
		$list = getPluginFiles('*.txt', 'ipBlocker');
		if ($list) {
			$files = array_merge(array('' => ''), $list);
		} else {
			$files = array('no text files found' => '');
		}
		$options = array(
				gettext('Action') => array('key' => 'ipBlocker_type', 'type' => OPTION_TYPE_RADIO,
						'order' => 5,
						'buttons' => $buttons,
						'desc' => gettext('How the plugin will interpret the IP list.')),
				gettext('IP list') => array('key' => 'ipBlocker_IP', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 10,
						'desc' => sprintf(gettext('List of IP ranges to %s.'), $text[(int) getOption('ipBlocker_type')])),
				' ' => array('key' => 'ipBlocker_button', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 30),
				gettext('Import list') => array('key' => 'ipBlocker_import', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 20,
						'selections' => $files,
						'nullselection' => '',
						'disabled' => count($files) == 1,
						'desc' => sprintf(gettext('Import an external IP list. <p class="notebox"><strong>NOTE:</strong> If this list is large it may exceed the capacity of netPhotoGraphics and %s to process and store the results.'), DATABASE_SOFTWARE))
		);

		return $options;
	}

	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'ipBlocker_IP':
				$key = 0;
				$list = self::getList();
				if (!empty($list)) {
					$list = array_values(sortMultiArray(self::getList(), ['start' => false]));

					foreach ($list as $key => $range) {
						$start = str_replace(' ', '', $range['start']);
						$start = preg_replace('`\.+`', '.', $start);
						$start = preg_replace('`::+`', '::', $start);

						$end = str_replace(' ', '', $range['end']);
						$end = preg_replace('`\.+`', '.', $end);
						$end = preg_replace('`::+`', '::', $end);
						?>
						<input id="ipholder_<?php echo $key; ?>a" type="textbox" size="35" name="ipBlocker_ip_start_<?php echo $key; ?>"
									 value="<?php echo html_encode($start); ?>" />
						-
						<input id="ipholder_<?php echo $key; ?>b" type="textbox" size="35" name="ipBlocker_ip_end_<?php echo $key; ?>"
									 value="<?php echo html_encode($end); ?>" />
						<br />
						<?php
					}
				}
				$i = $key;
				while ($i < $key + 4) {
					$i++;
					?>
					<input id="ipholder_<?php echo $i; ?>a" type="textbox" size="35" name="ipBlocker_ip_start_<?php echo $i; ?>"
								 value="" />
					-
					<input id="ipholder_<?php echo $i; ?>b" type="textbox" size="35" name="ipBlocker_ip_end_<?php echo $i; ?>"
								 value="" />
					<br />
					<?php
				}
				?>
				<script>
					<!--
				function clearips() {
						for (i = 0; i <= <?php echo $key + 4; ?>; i++) {
							$('#ipholder_' + i + 'a').val('');
							$('#ipholder_' + i + 'b').val('');
						}
					}
					//-->
				</script>
				<?php
				break;
			case 'ipBlocker_button':
				?>
				<p>
					<?php npgButton('button', gettext('clear list'), array('buttonClick' => "clearips();")); ?>
				</p>
				<?php
				break;
		}
	}

	static function handleOptionSave($themename, $themealbum) {
		$notify = '';
		self::getLock();

		$list = array();
		foreach ($_POST as $key => $param) {
			if ($param) {
				if (strpos($key, 'ipBlocker_ip_') !== false) {
					$p = explode('_', substr($key, 13));
					$list[$p[1]][$p[0]] = self::cononicalIP($param);
				}
			}
		}
		foreach ($list as $key => $range) {
			if (!array_key_exists('start', $range) || !array_key_exists('end', $range) || $range['start'] > $range['end']) {
				unset($list[$key]);
				$notify .= gettext('IP address range error') . '<br />';
			}
		}
		$list = array_unique($list, SORT_REGULAR);

		purgeOption('ipBlocker_import');
		if (!empty($_POST[postIndexEncode('ipBlocker_import')])) {
			$file = sanitize_path($_POST[postIndexEncode('ipBlocker_import')]);
			if (file_exists($file)) {
				$import = explode("\n", file_get_contents($file));
				foreach ($import as $ip) {
					$ip = trim($ip);
					if ($ip) {
						$ipblock = explode('-', $ip);
						if (!isset($ipblock[1])) {
							$ipblock[1] = $ipblock[0];
						}
						$list[] = array('start' => self::cononicalIP(trim($ipblock[0])), 'end' => self::cononicalIP(trim($ipblock[1])));
					}
				}
				$list = array_unique($list, SORT_REGULAR);
				if (empty($list)) {
					$list[] = array('start' => $start, 'end' => $end);
				}
			}
		}
		self::setList($list);
		self::releaseLock();

		if ($notify)
			return '&custom=' . $notify;
		else
			return false;
	}

	private static function getLock() {
		global $_ipBlocker_lists, $_ipBlockerMutex;
		$_ipBlockerMutex->lock();
		$_ipBlocker_list = NULL;
	}

	private static function releaseLock() {
		global $_ipBlockerMutex;
		$_ipBlockerMutex->unlock();
	}

	/**
	 * Utility to manage the ipBlocker lists
	 *
	 * @global array $_ipBlocker_lists
	 * @return array
	 */
	private static function getList() {
		global $_ipBlocker_list;
		if (!isset($_ipBlocker_list)) {
			if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists')) {
				$raw = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists');
				return getSerializedArray($raw);
			}
		}
		return array();
	}

	/**
	 * Stores ipBlocker lists
	 *
	 * @param string $which the name of the list
	 * @param array $list the content of the list
	 */
	private static function setList($list) {
		file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists', serialize($list));
	}

	/**
	 * Checks if ip is in the ipBlocker_list list and responds as per ipBlocker_type
	 * @return 0,1
	 */
	static function blocked($ip) {
		$list = self::getList();
		if (!empty($list)) {
			$ip = self::cononicalIP($ip);
			$gate = $allow = getOption('ipBlocker_type');
			foreach ($list as $range) {
				if ($ip >= $range['start'] && $ip <= $range['end']) {
					$gate = !$allow;
					break;
				}
			}
		} else {
			$gate = 0;
		}
		return $gate;
	}

	/**
	 * Monitors site access and excludes access if appropriate
	 * @param bool $check if true the access frequency will be checked
	 */
	static function load($ip) {
		if (self::blocked($ip)) {
			db_close();
			sleep(30);
			header("HTTP/1.0 503 " . gettext("Unavailable"));
			header("Status: 503 " . gettext("Unavailable"));
			exit(); //	terminate the script with no output
		}
	}

	/**
	 * creates ip strings that can be compared to each other
	 *
	 * @param IP $ip
	 * @return IP
	 */
	static function cononicalIP($ip) {
		if (str_contains($ip, ':')) {
			//	ipV6
			$sep = ':';
			if (strpos($ip, '::') !== FALSE) {
				$colons = str_pad('', 9 - substr_count($ip, ':'), ':');
				$ip = str_replace('::', $colons, $ip);
			}
			$ipa = array_slice(explode($sep, $ip . ':::::::'), 0, 8);
		} else {
			$sep = '.';
			$ipa = array_slice(explode($sep, $ip . '...'), 0, 4);
		}
		$ipc = '';
		foreach ($ipa as $sub) {
			$sub = ltrim($sub, '0');
			if (empty($sub)) {
				$sub = '0';
			}
			$ipc .= sprintf("%s%' 4s", $sep, $sub);
		}

		return ltrim($ipc, $sep);
	}

}

global $_ipBlockerMutex;
$_ipBlockerMutex = new npgMutex('bK');
if (extensionEnabled('ipBlocker')) {
	$ip = getUserIP();
	if (!isset($_current_admin_obj) || (!$me = $_current_admin_obj && !$_current_admin_obj->transient)) {
		$me = $ip == getOption('accessThreshold_Owner');
	} else {
		if ($_current_admin_obj->master) {
			if (getOption('accessThreshold_Owner') != $ip) {
				//	keep it mostly current if the user does not have a static IP
				setOption('accessThreshold_Owner', $ip);
			}
		}
	}
	if (!$me) {
		ipBlocker::load($ip);
	}
}
