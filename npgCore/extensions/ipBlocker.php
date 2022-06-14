<?php
/**
 * The plugin provides two services:
 * <ol>
 * 	<li>IP address filtering</li>
 * 	<li>Detection of <i>password probing</i> attempts
 * </ol>
 *
 * <b>IP address filtering:</b>
 *
 * Allows/Denies access to the gallery to specified IP address ranges
 * Detects repeated failed login attempts and blocks access to the IP address used
 * in these attempts.
 *
 * This does not block access to validated users, only anonymous visitors. But
 * a user will have to log on via the admin pages if out of the IP ranges as
 * he will get a Forbidden error on any front-end page including a logon form
 *
 * <b>Password probing:</b>
 *
 * Hackers often use <i>probing</i> or <i>password guessing</i> to attempt to breach your site
 * This plugin can help to throttle these attacks. It works by monitoring failed logon attempts.
 * If a defined threashold is exceeded by requests from a particular IP
 * address, further access attempts from that IP accress will be ignored until a timeout has expired.
 *
 * <b>IP list importing</b>
 *
 * IP addresses may be supplied in a text file, one IP per line. Upload the text file to the <i>%UPLOAD_FOLDER%</i> folder.

 * @author Stephen Billard (sbillard)
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/ipBlocker
 * @pluginCategory security
 */
$plugin_is_filter = 10 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Tools to block hacker access to your site.");
}

$option_interface = 'ipBlocker';

npgFilters::register('admin_login_attempt', 'ipBlocker::login', 0);
npgFilters::register('federated_login_attempt', 'ipBlocker::login', 0);
npgFilters::register('guest_login_attempt', 'ipBlocker::login', 0);
npgFilters::register('log_404', 'ipBlocker::handle404');
npgFilters::register('load_theme_script', 'ipBlocker::load');
npgFilters::register('admin_headers', 'ipBlocker::clear'); //	if we are logged in we should not be blocked

$_ipBlockerMutex = new npgMutex('bK');

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

		if (!file_exists(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists')) {
			$optons = [];
			$options['ipBlocker_type'] = getOption('ipBlocker_type') ? getOption('ipBlocker_type') == 'allow' : 1;
			$options['ipBlocker_threshold'] = getOption('ipBlocker_threshold') ? getOption('ipBlocker_threshold') : 10;
			$options['ipBlocker_404_threshold'] = getOption('ipBlocker_404_threshold') ? getOption('ipBlocker_404_threshold') : 10;
			$options['ipBlocker_timeout'] = getOption('ipBlocker_timeout') ? getOption('ipBlocker_timeout') : 60;
			$options['ipBlocker_flood_threshold'] = 240;
			self::setList('Options', $options);

			purgeOption('ipBlocker_type');
			purgeOption('ipBlocker_threshold');
			purgeOption('ipBlocker_404_threshold');
			purgeOption('ipBlocker_timeout');
			purgeOption('ipBlocker_forbidden');
			purgeOption('ipBlocker_list');

			$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `type`="ipBlocker", `subtype`="404" WHERE `type`="ipBlocker_404"';
			query($sql);
			$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `type`="ipBlocker", `subtype`="logon" WHERE `type`="ipBlocker_logon"';
			query($sql);
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$options = self::getList('Options');
		$buttons = array(gettext('Allow') => 1, gettext('Block') => 0);
		$text = array_flip($buttons);
		$options = array(
				gettext('IP list') => array('key' => 'ipBlocker_IP', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 10,
						'desc' => sprintf(gettext('List of IP ranges to %s.'), $text[$options['ipBlocker_type']])),
				' ' => array('key' => 'ipBlocker_button', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 20,
						'desc' => ''),
				gettext('Action') => array('key' => 'ipBlocker_type', 'type' => OPTION_TYPE_RADIO,
						'order' => 15,
						'buttons' => $buttons,
						'value' => $options['ipBlocker_type'],
						'desc' => gettext('How the plugin will interpret the IP list.')),
				gettext('Logon threshold') => array('key' => 'ipBlocker_threshold', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'value' => $options['ipBlocker_threshold'],
						'desc' => gettext('Admin page requests will be suspended after this many failed tries.')),
				gettext('404 threshold') => array('key' => 'ipBlocker_404_threshold', 'type' => OPTION_TYPE_NUMBER,
						'order' => 2,
						'value' => $options['ipBlocker_404_threshold'],
						'desc' => gettext('Access will be suspended after this many 404 errors.')),
//				gettext('Flooding threshold') => array('key' => 'ipBlocker_flood_threshold', 'type' => OPTION_TYPE_NUMBER,
//						'order' => 3,
//						'value' => $options['ipBlocker_flood_threshold'],
//						'desc' => gettext('Access will be suspended if there are more than this many theme page requests.')),
				'addl' => array('key' => 'note', 'type' => OPTION_TYPE_NOTE,
						'order' => 4,
						'desc' => gettext('Requests older than the 60 minutes are not counted. If a threshold value is zero, the blocking is disabled.')),
				gettext('Cool off') => array('key' => 'ipBlocker_timeout', 'type' => OPTION_TYPE_NUMBER,
						'order' => 5,
						'value' => $options['ipBlocker_timeout'],
						'desc' => gettext('The suspension will be removed after this many minutes.'))
		);
		$disabled = !extensionEnabled('ipBlocker');
		$cwd = getcwd();
		chdir(SERVERPATH . '/' . UPLOAD_FOLDER);
		$list = safe_glob('*.txt');
		chdir($cwd);
		if ($list) {
			$files = array('' => '');
			foreach ($list as $file) {
				$files[$file] = $file;
			}
		} else {
			$files = array('no text files found' => '');
			$disabled = true;
		}
		$options[gettext('Import list')] = array('key' => 'ipBlocker_import', 'type' => OPTION_TYPE_SELECTOR,
				'order' => 12,
				'selections' => $files,
				'nullselection' => '',
				'disabled' => $disabled,
				'desc' => sprintf(gettext('Import an external IP list. <p class="notebox"><strong>NOTE:</strong> If this list is large it may exceed the capacity of netPhotoGraphics and %s to process and store the results.'), DATABASE_SOFTWARE)
		);

		if (!extensionEnabled('ipBlocker')) {
			$options['note'] = array('key' => 'ipBlocker_note', 'type' => OPTION_TYPE_NOTE,
					'order' => 0,
					'desc' => '<p class="notebox">' . gettext('IP list ranges cannot be managed with the plugin disabled') . '</p>');
		}
		return $options;
	}

	function handleOption($option, $currentValue) {
		if (extensionEnabled('ipBlocker')) {
			$disabled = '';
		} else {
			$disabled = ' disabled="disabled"';
		}

		switch ($option) {
			case 'ipBlocker_IP':
				$list = self::getList('Suspend');
				$key = 0;
				foreach ($list as $key => $range) {
					?>
					<input id="ipholder_<?php echo $key; ?>a" type="textbox" size="15" name="ipBlocker_ip_start_<?php echo $key; ?>"
								 value="<?php echo html_encode(str_replace(' ', '', $range['start'])); ?>" <?php echo $disabled; ?> />
					-
					<input id="ipholder_<?php echo $key; ?>b" type="textbox" size="15" name="ipBlocker_ip_end_<?php echo $key; ?>"
								 value="<?php echo html_encode(str_replace(' ', '', $range['end'])); ?>" <?php echo $disabled; ?> />
					<br />
					<?php
				}
				$i = $key;
				while ($i < $key + 4) {
					$i++;
					?>
					<input id="ipholder_<?php echo $i; ?>a" type="textbox" size="15" name="ipBlocker_ip_start_<?php echo $i; ?>"
								 value="" <?php echo $disabled; ?> />
					-
					<input id="ipholder_<?php echo $i; ?>b" type="textbox" size="15" name="ipBlocker_ip_end_<?php echo $i; ?>"
								 value="" <?php echo $disabled; ?> />
					<br />
					<?php
				}
				?>
				<script type="text/javascript">
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
		if (!empty($_POST[postIndexEncode('ipBlocker_import')])) {
			$file = SERVERPATH . '/' . UPLOAD_FOLDER . '/' . sanitize_path($_POST[postIndexEncode('ipBlocker_import')]);
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
		self::setList('Suspend', $list);

		$optons = [];
		$options['ipBlocker_type'] = getOption('ipBlocker_type');
		$options['ipBlocker_threshold'] = getOption('ipBlocker_threshold');
		$options['ipBlocker_404_threshold'] = getOption('ipBlocker_404_threshold');
		$options['ipBlocker_flood_threshold'] = getOption('ipBlocker_flood_threshold');
		$options['ipBlocker_timeout'] = getOption('ipBlocker_timeout');
		self::setList('Options', $options);

		self::releaseLock();

		purgeOption('ipBlocker_import');
		purgeOption('ipBlocker_type');
		purgeOption('ipBlocker_threshold');
		purgeOption('ipBlocker_404_threshold');
		purgeOption('ipBlocker_flood_threshold');
		purgeOption('ipBlocker_timeout');

		if ($notify)
			return '&custom=' . $notify;
		else
			return false;
	}

	/**
	 * Clears out the suspension list for an ip, for instance when an admin logs
	 * on with that ip
	 *
	 * @global object $_current_admin_obj
	 * @return boolean
	 */
	static function clear() {
		global $_current_admin_obj;
		self::getLock();
		$suspend = self::getList('Suspend');
		if ($suspend && npg_loggedin() && !$_current_admin_obj->transient) {
			$ip = getUserIP();
			$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type` ="ipBlocker" AND `data`=' . db_quote($ip);
			query($sql);

			if (array_key_exists($ip, $suspend)) {
				unset($suspend[$ip]);
				if (empty($suspend)) {
					$suspend = array();
				}
				self::setList('Suspend', $suspend);
			}
			$result = true;
		} else {
			$result = false;
		}
		self::releaseLock();
		return $result;
	}

	/**
	 * Monitors Login attempts and suspends of past failure threshold
	 * @param bit true if login is successful
	 * @param string $user ignored
	 * @param string $pass ignored
	 */
	static function login($loggedin, $user, $pass = NULL, $auth = NULL) {
		if ($loggedin) {
			self::clear();
		} else {
			self::ipGate('logon');
			self::load();
		}
		return $loggedin;
	}

	/**
	 * causes 404 errors to be monitored for abuse
	 *
	 * @param type $log ignored
	 * @param type $data ignored
	 * @return type
	 */
	static function handle404($log, $data) {
		self::ipGate('404');
		return $log;
	}

	/**
	 * Checks if ip should be suspended
	 * @param bool $allow ignored
	 * @param string $page ignored
	 */
	static function ipGate($type) {
		$options = self::getList('Options');
		if (empty($options)) {
			$threshold = 0;
		} else {
			switch ($type) {
				case 'logon':
					$threshold = $options['ipBlocker_threshold'];
					break;
				case '404':
					$threshold = $options['ipBlocker_404_threshold'];
					break;
				case 'flood':
					$threshold = $options['ipBlocker_flood_threshold'];
					break;
			}
		}
		if ($threshold) {
			self::getLock();
			$suspend = self::getList('Suspend');
			$ip = self::cononicalIP(getUserIP());
			//	clean out expired attempts
			$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="ipBlocker" AND `aux` < ' . db_quote(time() - 3600);
			query($sql);
			//	add this attempt
			$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`,`data`) VALUES ("ipBlocker",' . db_quote($type) . ', ' . db_quote(time()) . ',' . db_quote($ip) . ')';
			query($sql);
			//	check how many times this has happened recently
			$sql = 'SELECT `aux` FROM ' . prefix('plugin_storage') . ' WHERE `type`="ipBlocker" AND `subtype`=' . db_quote($type) . ' AND `data`=' . db_quote($ip) . ' ORDER BY `aux`';
			$instances = query_full_array($sql);
			$frequency = 0;
			$count = count($instances);
			if ($count > 5) {
				for ($i = 1; $i < $count; $i++) {
					$frequency = $frequency + $instances[$i]['aux'] - $instances[$i - 1]['aux'];
				}
				$frequency = $frequency / $count;
				$minInterval = 3600 / $threshold;
				if ($frequency < $minInterval) {
					npgFilters::apply('security_misc', 4, $type, 'ipBlocker', getRequestURI());
					$suspend[$ip] = time();
					self::setList('Suspend', $suspend);
					$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type` ="ipBlocker" AND `data`=' . db_quote($ip);
					query($sql);
				}
			}
			self::releaseLock();
		}
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
	private static function getIPBlockerLists() {
		global $_ipBlocker_lists;

		if (empty($_ipBlocker_lists)) {
			if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists')) {
				$raw = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists');
				$_ipBlocker_lists = unserialize($raw);
			} else {
				$_ipBlocker_lists['Options']['ipBlocker_type'] = 1;
				$_ipBlocker_lists['Options']['ipBlocker_threshold'] = 10;
				$_ipBlocker_lists['Options']['ipBlocker_404_threshold'] = 10;
				$_ipBlocker_lists['Options']['ipBlocker_timeout'] = 60;
				$_ipBlocker_lists['Options']['ipBlocker_flood_threshold'] = 240;
			}
		}
		return $_ipBlocker_lists;
	}

	/**
	 * Fetches ipBlocker lists (avoid database)
	 *
	 * @param string $which the name of the list
	 * @return array
	 */
	private static function getList($which) {
		$lists = self::getIPBlockerLists();
		if (isset($lists[$which])) {
			return $lists[$which];
		}
		return array();
	}

	/**
	 * Stores ipBlocker lists
	 *
	 * @param string $which the name of the list
	 * @param array $list the content of the list
	 */
	private static function setList($which, $list) {
		global $_ipBlocker_lists;
		self::getIPBlockerLists();
		$_ipBlocker_lists[$which] = $list;
		file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/ipBlockerLists', serialize($_ipBlocker_lists));
	}

	/**
	 * Checks if ip is in the ipBlocker_list list and responds as per ipBlocker_type
	 * @return 0,1
	 */
	static function blocked() {
		$list = self::getList('ipBlocker_list');
		if (!empty($list)) {
			$options = self::getList('Options');
			$gate = $allow = $optons['ipBlocker_type'];
			$ip = self::cononicalIP(getUserIP());
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
	 * Checks the suspension list for the ip
	 * @return boolean
	 */
	static function suspended() {
		self::getLock();
		$suspend = self::getList('Suspend');
		$result = false;
		if (!empty($suspend)) {
			if (array_key_exists($ip = getUserIP(), $suspend)) {
				if ($suspend[$ip] < (time() - $suspend['ipBlocker_timeout'] * 60)) {
					// cooloff period passed
					unset($suspend[$ip]);
					self::setList('Suspend', $suspend);
				}
			} else {
				$result = true;
			}
		}
		self::releaseLock();
		return $result;
	}

	/**
	 * Monitors front end access and excludes access if appropriate
	 * @param bool $check if true the access frequency will be checked
	 */
	static function load() {
		if (self::blocked() || self::suspended()) {
			if (!self::clear()) {
				sleep(30);
				header("HTTP/1.0 503 " . gettext("Unavailable"));
				header("Status: 503 " . gettext("Unavailable"));
				header("Retry-After: 300");
				exit(); //	terminate the script with no output
			}
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
			$sep = ':';
		} else {
			$sep = '.';
		}
		$ipa = explode($sep, $ip);
		$ipc = '';
		foreach ($ipa as $sub) {
			$ipc .= sprintf("%s%' 4s", $sep, $sub);
		}
		return ltrim($ipc, $sep);
	}

}
